import re
import os

def main():
    with open('d:/laragon/www/Repository/resources/views/incoming-mails/index.blade.php', 'r', encoding='utf-8') as f:
        incoming_content = f.read()

    with open('d:/laragon/www/Repository/resources/views/outgoing-mails/index.blade.php', 'r', encoding='utf-8') as f:
        outgoing_content = f.read()

    # Extract the modals section from incoming
    revision_modal = re.search(r'<!-- Revision Note Modal -->.*?</template>', incoming_content, re.DOTALL).group(0)
    return_modal = re.search(r'<!-- Return Signature Workflow Modal -->.*?</template>', incoming_content, re.DOTALL).group(0)
    
    # Extract Custom Confirmation Modal
    confirm_modal_incoming = re.search(r'<!-- Custom Confirmation Modal -->.*?</div>\n        </div>', incoming_content, re.DOTALL).group(0)
    
    # Extract Toast Notification
    toast_container = re.search(r'<!-- Success Toast Notification -->.*?</div>\n        </div>', incoming_content, re.DOTALL).group(0)

    # Extract Teleported Single Status Dropdown
    dropdown_incoming = re.search(r'<!-- Teleported Single Status Dropdown.*?.*?</template>', incoming_content, re.DOTALL).group(0)
    
    # Replace "Return" with "Receive" in Modals
    receive_modal = return_modal.replace('Return', 'Receive')
    receive_modal = receive_modal.replace('return', 'receive')
    receive_modal = receive_modal.replace('pengembalian', 'penerimaan')
    receive_modal = receive_modal.replace('Pengembalian', 'Penerimaan')
    receive_modal = receive_modal.replace('RETURN', 'RECEIVE')

    # Extract Alpine JS from incoming
    alpine_js = re.search(r'<script>.*?</script>', incoming_content, re.DOTALL).group(0)
    
    # Adapt Alpine JS for outgoing
    alpine_js = alpine_js.replace('incomingMailSelector', 'outgoingMailStatusHandler')
    alpine_js = alpine_js.replace('IncomingMailStatus', 'OutgoingMailStatus')
    alpine_js = alpine_js.replace('incoming-mails', 'outgoing-mails')
    alpine_js = alpine_js.replace('Return', 'Receive')
    
    # Carefully replace return to receive for variables and functions
    variables = ['returnRecipients', 'returnCanvases', 'returnDirty', 'returnError', 'returnMailIds', 'returnMailLabel', 'isLoadingReturn', 'isSavingReturn', 'returnDrawingKey', 'returnPreviousFocus', 'openReturnWorkflow', 'closeReturnModal', 'saveReturnWorkflow', 'addReturnRecipient', 'removeReturnRecipient', 'newReturnRecipient', 'showReturnModal', 'setupReturnCanvas', 'startReturnSignature', 'drawReturnSignature', 'finishReturnSignature', 'clearReturnSignature', 'returnCanvasPosition']
    for v in variables:
        alpine_js = alpine_js.replace(v, v.replace('return', 'receive').replace('Return', 'Receive'))
    
    # Revert specific things
    alpine_js = alpine_js.replace('WAITING_RECEIVE', 'WAITING_RETURN')
    alpine_js = alpine_js.replace('return-recipients', 'receive-recipients')
    alpine_js = alpine_js.replace('route(\'outgoing-mails.return-workflow.store', 'route(\'outgoing-mails.receive-workflow.store')

    # In outgoing content, find where to insert
    # We find "<!-- Confirmation Modal -->" or "<!-- Custom Confirmation Modal -->"
    insert_point = outgoing_content.find('<!-- Confirmation Modal -->')
    if insert_point == -1:
        insert_point = outgoing_content.find('<!-- Custom Confirmation Modal -->')
    
    part1 = outgoing_content[:insert_point]
    
    # Find table section from outgoing content
    table_section = re.search(r'<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between pb-4 border-b border-slate-200/80 gap-4">.*?</script>', outgoing_content, re.DOTALL).group(0)
    table_section = re.sub(r'<script>.*', '', table_section, flags=re.DOTALL)
    
    # Combine
    new_content = (
        part1 + 
        toast_container + "\n\n" +
        confirm_modal_incoming + "\n\n" +
        dropdown_incoming + "\n\n" +
        revision_modal + "\n\n" +
        receive_modal + "\n\n" +
        table_section + "\n" +
        alpine_js + "\n" +
        "@endsection\n"
    )

    with open('d:/laragon/www/Repository/resources/views/outgoing-mails/index.blade.php', 'w', encoding='utf-8') as f:
        f.write(new_content)

if __name__ == '__main__':
    main()
