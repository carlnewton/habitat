/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/global.scss';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'dropzone/dist/dropzone.css';
import Dropzone from "dropzone";
import 'htmx.org';

window.htmx = require('htmx.org');
require('bootstrap');

// TODO: Move dropzone to a file which is only loaded upon posting
let dropzone = new Dropzone(".dropzone", { 
    url: "post/upload",
    maxFilesize: 99,
    acceptedFiles: "image/*,video/*",
    addRemoveLinks: true,
    dictDefaultMessage: "<i class=\"bi fs-2 bi-upload\"></i><br>Add photos / videos",
    success: function (file, response) {
        let attachmentIdString = document.getElementById('attachmentIds').value;
        let attachmentIdArray = attachmentIdString.split(',');
        attachmentIdArray.push(response.id);
        document.getElementById('attachmentIds').value = attachmentIdArray.join(',');
    }
});
