import json
import sys

def main():
    with open('C:/Users/muham/.gemini/antigravity-ide/brain/4ed0f5fc-4e2f-46ab-804d-ac19630b3242/.system_generated/logs/transcript.jsonl', 'r', encoding='utf-8') as f:
        for line in f:
            data = json.loads(line)
            if data.get('type') == 'PLANNER_RESPONSE':
                for tool_call in data.get('tool_calls', []):
                    # find the tool call response
                    pass
            elif data.get('type') == 'TOOL_RESPONSE':
                if 'view_file' in data.get('name', ''):
                    content = data.get('content', '')
                    if 'Total Lines: 490' in content and 'File Path: `file:///d:/laragon/www/Repository/resources/views/outgoing-mails/index.blade.php`' in content:
                        # Extract everything after "1: "
                        # The format is `<line_number>: <original_line>`
                        lines = content.split('\n')
                        reconstructed_lines = []
                        is_code = False
                        for l in lines:
                            if is_code or l.startswith('1: '):
                                is_code = True
                                # strip line number
                                if ': ' in l:
                                    parts = l.split(': ', 1)
                                    if parts[0].isdigit():
                                        reconstructed_lines.append(parts[1])
                                    else:
                                        reconstructed_lines.append(l)
                                else:
                                    reconstructed_lines.append(l)
                                
                        # write to file
                        # We need to remove the "The above content shows the entire, complete file contents of the requested file." line
                        final_lines = []
                        for l in reconstructed_lines:
                            if "The above content shows the entire, complete file contents" in l:
                                continue
                            final_lines.append(l)
                        
                        with open('d:/laragon/www/Repository/resources/views/outgoing-mails/index.blade.php', 'w', encoding='utf-8') as out:
                            out.write('\n'.join(final_lines))
                        print("Successfully restored outgoing-mails/index.blade.php to 490 lines version.")
                        return

if __name__ == '__main__':
    main()
