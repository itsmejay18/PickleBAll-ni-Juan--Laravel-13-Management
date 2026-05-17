import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

document.addEventListener('DOMContentLoaded', () => {
    const mapElement = document.getElementById('locationMap');

    if (!mapElement || mapElement.dataset.mapReady === 'true') {
        return;
    }

    mapElement.dataset.mapReady = 'true';

    const venueLocation = [6.77025, 125.2115287];
    const map = L.map(mapElement, {
        scrollWheelZoom: false,
    }).setView(venueLocation, 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    L.marker(venueLocation)
        .addTo(map)
        .bindPopup('<strong>Pickle Ballan ni Juan</strong><br>Tagged venue location')
        .openPopup();

    window.setTimeout(() => map.invalidateSize(), 100);
});
