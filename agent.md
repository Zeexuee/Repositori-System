# AI Agent Instructions for Corporate Secretariat Repository System

## 1. Project Context
You are assisting in the development of a Corporate Secretariat Repository System for a large enterprise. This system handles highly sensitive documents, digital signatures (PSrE), and strict organizational workflows.
Security, auditability, and absolute adherence to business logic are the highest priorities.

## 2. Tech Stack & Infrastructure
- **Backend:** Laravel (PHP)
- **Frontend Tooling:** Vite + Tailwind CSS / Standard UI component library
- **Database:** MySQL
- **Background Processing:** Redis + Laravel Queue + Supervisor
- **Storage:** Amazon S3 (Object Storage)
- **Deployment:** VPS (Linux) - Do NOT write configurations for shared hosting environments.

## 3. Architectural Rules (Backend)
- **Thin Controllers:** Controllers must NOT contain business logic or state transition logic. Use Service classes, Action classes, or the State Pattern to handle complex workflows.
- **Strict Validation:** Use Laravel Form Requests for every inbound HTTP request.
- **State Machine Enforcement:** Outgoing and Incoming mails have strict states. Ensure validations reject any unauthorized or out-of-sequence state transitions (e.g., `DRAFT` cannot jump to `SIGNED`).
- **Asynchronous Processing:** Any task taking > 3 seconds MUST be dispatched to a Laravel Queue using Redis. This includes:
  - OCR Processing (e.g., Tesseract).
  - External API calls to Digital Signature Providers (PSrE).
  - Email notifications for dispositions.
- **Immutable Audit Trail:** Every action (Create, Read, Update, Delete/Archive, Download, Sign) must be logged with User ID, IP Address, Action Type, and Timestamp. Audit logs must never be mutable.
- **Storage Isolation:** Never store physical documents on local disk (`storage/app/public`). All document uploads must go directly to Amazon S3. Database should only store the S3 path/URL.

## 4. UI/UX & Frontend Constraints
- **Design System:** Use Modern Corporate Flat / Semi-Flat UI.
- **PROHIBITED DESIGN:** Do NOT use "Liquid Glass", "Glassmorphism", excessive blur, or translucent backgrounds. 
- **Accessibility:** Ensure high contrast ratios complying with WCAG 2.1 standards. Use solid backgrounds (white/light gray) for document viewers and data tables to prevent visual fatigue.
- **Modularity:** Use a strict Card-Based Layout to separate functional areas.

## 5. Security & RBAC
- Implement strict Role-Based Access Control (RBAC). 
- Verify user authorization not just at the UI level, but on every single API endpoint/controller method using Laravel Gates or Policies.
- Ensure document retrieval from S3 generates temporary, signed URLs rather than permanent public links.

## 6. Coding Standards
- Write strict, strongly typed PHP code (declare strict types).
- Return standard HTTP status codes (e.g., 403 for unauthorized state changes, 422 for validation errors, 200/201 for success).
- Maintain an objective, critical, and direct tone in code comments. Avoid unnecessary filler words.