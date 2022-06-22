import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class MappingService {

    constructor () {
        this.repository = RepositoryFactory.get('map')
        this.locations = []
        this.geoDataItems = []
        this.editableLayers = null
        this.markingInfo = {
            id: 0,
            name: '',
            lat: 0,
            lon: 0
        }
    }

    async getSearchResult (name, filtered_types) {
        try {
            let response = await this.repository.get(name)
            if (response.status === 200) {
                let { data } = response
                this.geoData = this.filterResultsOut(data, filtered_types)

                return this.geoData
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {

            return new ErrorHandler(e, 'http')
        }

    }

    filterResultsOut (geoData, filtered_types) {
        let result = []
        this.geoDataItems = []
        for (let i in geoData) {

            let geoType = geoData[i].geojson.type
            if (Object.keys(filtered_types).length > 0 && !(geoType.toLowerCase() in filtered_types)) {
                continue
            }
            geoData[i].searched = true
            result.push(geoData[i])
        }

        return result
    }

    strToHex (str) {
        str += 'z4795dfjkldfnjk4lnjkl'
        let hash = 0
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash)
        }
        let colour = '#'
        for (let i = 0; i < 3; i++) {
            let value = (hash >> (i * 8)) & 0xFF
            colour += ('00' + value.toString(16)).substr(-2)
        }
        return colour
    }

    focusLocation (geo) {
        let tmp = []
        tmp.push(geo)
        return tmp

    }

    manualDrawingLocationConvert (geoDataItem) {
        let locations = []
        for (let i = 0; i < geoDataItem.geojson.coordinates.length; i++) {
            let coordinates = geoDataItem.geojson.coordinates[i]
            for (let j = 0; j < coordinates.length; j++) {
                let coordinate = coordinates[j]
                if (coordinate.lat === undefined && coordinate.lng === undefined) {
                    locations.push(coordinate)
                } else {

                    locations.push([coordinate.lat, coordinate.lng])
                }
            }
        }
        geoDataItem.geojson.coordinates[0] = []
        locations.forEach((e) => {
            geoDataItem.geojson.coordinates[0].push(e)
        })

        return geoDataItem

    }

    createMarkingInformation (id, name,serialNumber, lat, lon,data_stream) {
        this.markingInfo = {

            id: id,
            serialNumber:serialNumber,
            name: name,
            lat: lat,
            lon: lon,
            dataStream:data_stream
        }

        return this.markingInfo

    }
}
