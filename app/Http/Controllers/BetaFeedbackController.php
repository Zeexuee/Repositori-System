<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBetaFeedbackRequest;
use App\Models\BetaFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BetaFeedbackController extends Controller
{
    public function store(StoreBetaFeedbackRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $user = auth()->user();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('beta-feedback-attachments', 'local');
        }

        $feedback = BetaFeedback::create([
            'user_id' => $user->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'page_url' => $request->input('page_url') ?: url()->previous(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'attachment_path' => $attachmentPath,
            'status' => 'PENDING',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan error/feedback berhasil dikirim! Terima kasih atas kontribusi Anda pada Open Beta.',
                'data' => $feedback,
            ]);
        }

        return redirect()->back()->with('success', 'Laporan error/feedback berhasil dikirim! Terima kasih atas kontribusi Anda pada Open Beta.');
    }

    public function history(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = BetaFeedback::with('user:id,name,email');

        // Jika bukan Direksi/Super Admin, batasi hanya melihat laporan milik sendiri
        if (!$user->hasRole('Direksi') && !$user->hasRole('Super Admin')) {
            $query->where('user_id', $user->id);
        }

        $feedbacks = $query->latest()->take(50)->get()->map(function (BetaFeedback $item) use ($user) {
            return [
                'id' => $item->id,
                'user_name' => $item->user?->name ?? 'User',
                'user_email' => $item->user?->email ?? '-',
                'is_owner' => $item->user_id === $user->id,
                'category' => $item->category,
                'category_label' => $item->category_label,
                'title' => $item->title,
                'description' => $item->description,
                'page_url' => $item->page_url,
                'ip_address' => $item->ip_address,
                'attachment_url' => $item->attachment_path ? route('document.download', ['path' => $item->attachment_path, 'inline' => 1]) : null,
                'status' => $item->status,
                'status_label' => $item->status_label,
                'status_badge' => $item->status_badge_classes,
                'admin_notes' => $item->admin_notes,
                'created_at_formatted' => $item->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'feedbacks' => $feedbacks,
            'can_manage' => $user->hasRole('Direksi') || $user->hasRole('Super Admin'),
        ]);
    }

    public function updateStatus(Request $request, BetaFeedback $feedback): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('Direksi') && !$user->hasRole('Super Admin')) {
            abort(403, 'Anda tidak memiliki otoritas untuk memperbarui status laporan ini.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:PENDING,IN_PROGRESS,RESOLVED,CLOSED'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $feedback->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $feedback->admin_notes,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Status laporan berhasil diperbarui.',
                'data' => $feedback,
            ]);
        }

        return redirect()->back()->with('success', 'Status laporan berhasil diperbarui.');
    }
}
