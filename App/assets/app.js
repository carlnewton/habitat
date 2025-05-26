import './styles/global.scss';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'htmx.org';

window.htmx = require('htmx.org');
require('bootstrap');

const offcanvasNavbar = document.getElementById('offcanvasNavbar')
const sidebar = document.getElementById('sidebar');
const sidebarContainer = document.getElementById('sidebar-container');
const offcanvasSidebarContainer = document.getElementById('offcanvas-sidebar-container');

if (offcanvasNavbar) {
    offcanvasNavbar.addEventListener('show.bs.offcanvas', event => {
        if (!offcanvasSidebarContainer) {
            return;
        }
        
        if (!sidebar) {
            return;
        }
    
        offcanvasSidebarContainer.appendChild(sidebar);
        offcanvasSidebarContainer.classList.add('drawer-padding');
        
    });
    offcanvasNavbar.addEventListener('hide.bs.offcanvas', event => {
        if (!offcanvasSidebarContainer) {
            return;
        }
        
        if (!sidebar) {
            return;
        }
    
        sidebarContainer.appendChild(sidebar);
        offcanvasSidebarContainer.classList.remove('drawer-padding');
    });
}
