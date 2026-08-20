const map = L.map('map').setView([50.435, 15.353], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 20,
}).addTo(map);

let kuBoundaryLayers = [];

fetch('data/ku_boundaries.geojson')
    .then(response => response.json())
    .then(data => {
        L.geoJSON(data, {
            style: { color: '#d61010', weight: 2, dashArray: '6 4', fill: false },
            onEachFeature: (feature, layer) => {
                layer.bindTooltip(feature.properties.name, { permanent: true, direction: 'center', className: 'ku-label' });
                kuBoundaryLayers.push(layer);
            },
        }).addTo(map);

        updateKuLabels();
    });

function updateKuLabels() {
    const hide = map.getZoom() >= 17;
    document.getElementById('map').classList.toggle('hide-ku-labels', hide);
}

map.on('zoomend', updateKuLabels);


const LABEL_MIN_ZOOM = 19;
const LABEL_MAX_FEATURES = 350;
let parcelsLayer = null;

function loadParcels() {
    if (map.getZoom() < 17) {
        if (parcelsLayer) {
            map.removeLayer(parcelsLayer);
            parcelsLayer = null;
        }
        return;
    }

    const bounds = map.getBounds();
    const params = new URLSearchParams({
        minLat: bounds.getSouth(),
        minLon: bounds.getWest(),
        maxLat: bounds.getNorth(),
        maxLon: bounds.getEast(),
        zoom: map.getZoom(),
    });

    fetch('api/parcels.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (parcelsLayer) {
                map.removeLayer(parcelsLayer);
            }

            const showLabels = map.getZoom() >= LABEL_MIN_ZOOM || data.features.length <= LABEL_MAX_FEATURES;

            parcelsLayer = L.geoJSON(data, {
                style: { color: '#2563eb', weight: 1, fillOpacity: 0.1 },
                onEachFeature: (feature, layer) => {
                    const props = feature.properties;
                    layer.bindPopup(
                        `<strong>Parcela ${props.parcel_number}</strong><br>` +
                        `Výměra: ${props.area_m2 ?? '?'} m²<br>` +
                        `Katastrální reference: ${props.national_reference}`
                    );

                    if (showLabels) {
                        layer.bindTooltip(props.parcel_number, {
                            permanent: true,
                            direction: 'center',
                            className: 'parcel-label',
                        });
                    }
                },
            }).addTo(map);
        });
}


map.on('moveend', loadParcels);
loadParcels();
