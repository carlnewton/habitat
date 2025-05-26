import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import { Modal } from 'bootstrap'

const tteditor = new Editor({
    element: document.querySelector('.editor'),
    content: document.getElementById('sidebarContent').value,
    extensions: [
        StarterKit,
        Link.configure({
            openOnClick: false,
            // The HTMLAttributes are for admin submitted content only. If using this as a starting point for user
            // submitted links, should probably remove these two lines.
            HTMLAttributes: {
                rel: null,
                target: null,
            },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'focus-ring focus-ring-light',
        },
    },
    onUpdate({ editor }) {
        let sidebarContent = editor.getHTML();
        if (sidebarContent.trim() === '<p></p>') {
            sidebarContent = '';
        }
        document.getElementById('sidebarContent').value = sidebarContent;
    },
})

document.querySelector('.ttBtnH3').onclick = function() {
    tteditor.chain().focus().toggleHeading({ level: 3 }).run();
}

document.querySelector('.ttBtnUl').onclick = function() {
    tteditor.chain().focus().toggleBulletList().run();
}

document.querySelector('.ttBtnA').onclick = function() {
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

document.getElementById('ttBtnASubmit').onclick = function() {
    let linkUrlField = document.getElementById('linkUrl');
    tteditor.chain().focus().extendMarkRange('link').unsetLink().run();
    if (linkUrlField.value.trim() !== '') {
        tteditor.chain().focus().extendMarkRange('link').setLink({ href: linkUrlField.value }).run();
    }
}

document.getElementById('ttBtnARemove').onclick = function() {
    tteditor.chain().focus().extendMarkRange('link').unsetLink().run();
}
