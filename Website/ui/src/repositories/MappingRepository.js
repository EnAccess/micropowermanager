import Client from './Client/AxiosClient'
const resource = {
    openStreetSearch: 'https://nominatim.openstreetmap.org/search.php?q=',
}

export default {
    get(name) {
        return Client.get(
            `${resource.openStreetSearch + name + '&polygon_geojson=1&format=json'}`,
        )
    },
}
