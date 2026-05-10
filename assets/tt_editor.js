import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { Placeholder } from '@tiptap/extensions'
import { Modal } from 'bootstrap'

initTiptapEditor();

function initTiptapEditor() {
    let tteditor = new Editor({
        element: document.querySelector('.editor'),
        content: document.getElementById('ttContent').value,
        extensions: [
            StarterKit.configure({
                link: {
                    openOnClick: false,
                    enableClickSelection: true,
                }
            }),
            Placeholder.configure({
                placeholder: document.querySelector('.editor').dataset.placeholder,
            }),
        ],
        editorProps: {
            attributes: {
                class: 'focus-ring focus-ring-light min-height-100',
            },
        },
        onUpdate({ editor }) {
            let ttContent = editor.getHTML();
            if (ttContent.trim() === '<p></p>') {
                ttContent = '';
            }
            document.getElementById('ttContent').value = ttContent;
        },
        onFocus({ editor, event }) {
            let wysiwygTools = document.querySelector('.wysiwyg-tools');
            wysiwygTools && wysiwygTools.classList.remove('d-none');
        },
    })

    let ttBtnH3 = document.querySelector('.ttBtnH3')
    if (ttBtnH3) {
        ttBtnH3.onclick = function() {
            tteditor.chain().focus().toggleHeading({ level: 3 }).run();
        }
    }
    
    let ttBtnUl = document.querySelector('.ttBtnUl')
    if (ttBtnUl) {
        ttBtnUl.onclick = function() {
            tteditor.chain().focus().toggleBulletList().run();
        }
    }
    
    let ttBtnA = document.querySelector('.ttBtnA')
    if (ttBtnA) {
        ttBtnA.onclick = function() {
            let linkUrlField = document.getElementById('linkUrl');
            let linkModal = new Modal(document.getElementById('linkModal'));
            let existingLink = tteditor.getAttributes('link');
            let title = document.getElementById('insertUpdateHyperlinkTitle');
            let submitBtn = document.getElementById('ttBtnASubmit');
            let removeBtn = document.getElementById('ttBtnARemove');
        
            if (existingLink.href === undefined) {
                title.textContent = title.dataset.insert;
                submitBtn.textContent = submitBtn.dataset.insert;
                removeBtn.classList.add('d-none');
                linkUrlField.value = '';
            } else {
                title.textContent = title.dataset.update;
                submitBtn.textContent = submitBtn.dataset.update;
                linkUrlField.value = existingLink.href;
                removeBtn.classList.remove('d-none');
            }
        
            linkModal.show();
            linkUrlField.focus();
        }
    }

    document.getElementById('ttBtnASubmit').onclick = function() {
        let linkUrlField = document.getElementById('linkUrl');
        tteditor.chain().focus().extendMarkRange('link').unsetLink().run();
        if (linkUrlField.value.trim() !== '') {
            tteditor.chain().focus().extendMarkRange('link').setLink({ href: linkUrlField.value }).run();
            if (tteditor.view.state.selection.empty) {
                tteditor.commands.insertContent(linkUrlField.value);
            }
        }
    }
    
    document.getElementById('ttBtnARemove').onclick = function() {
        tteditor.chain().focus().extendMarkRange('link').unsetLink().run();
    }
}

document.addEventListener('htmx:afterRequest', function(event) {
    if (event.detail.pathInfo.requestPath !== '/hx/add-comment') {
        return;
    }
    initTiptapEditor();
});
