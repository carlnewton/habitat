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
    offcanvasNavbar.addEventListener('show.bs.offcanvas', () => {
        if (offcanvasSidebarContainer && sidebar) {
            offcanvasSidebarContainer.appendChild(sidebar);
            offcanvasSidebarContainer.classList.add('drawer-padding');
        }
    });

    offcanvasNavbar.addEventListener('hide.bs.offcanvas', () => {
        if (offcanvasSidebarContainer && sidebar) {
            sidebarContainer.appendChild(sidebar);
            offcanvasSidebarContainer.classList.remove('drawer-padding');
        }
    });
}

let nearbyLatLng = '';
let nearbyLinks = document.getElementsByClassName('nearby-link');
for (let i = 0; i < nearbyLinks.length; i++) {
    nearbyLinks[i].addEventListener('htmx:confirm', async function(event) {
        event.preventDefault();

        navigator.geolocation.getCurrentPosition(
            (position) => {
                let homeLinks = document.querySelectorAll('.home-link');
                for (let h = 0; h < homeLinks.length; h++) {
                    homeLinks[h].classList.remove('active');
                    homeLinks[h].querySelector('i').classList.remove('bi-house-fill');
                    homeLinks[h].querySelector('i').classList.add('bi-house');
                }
                let nearbyLinks = document.querySelectorAll('.nearby-link');
                for (let n = 0; n < nearbyLinks.length; n++) {
                    nearbyLinks[n].classList.add('active');
                    nearbyLinks[n].querySelector('i').classList.remove('bi-geo-alt');
                    nearbyLinks[n].querySelector('i').classList.add('bi-geo-alt-fill');
                }
                nearbyLatLng = position.coords.latitude + ',' + position.coords.longitude;
                document.title = event.srcElement.dataset.nearbytitle;
                event.detail.issueRequest();
            },
            (error) => {
                alert(event.srcElement.dataset.errormessage)
            },
        );
    });

    nearbyLinks[i].addEventListener('htmx:configRequest', async function(event) {
        event.detail.parameters['latLng'] = nearbyLatLng;
    });
}
