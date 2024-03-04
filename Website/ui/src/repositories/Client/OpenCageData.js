import axios from 'axios'

//https://opencagedata.com/api
//we have 2500 requests per day. 1 request per 1 second. It looks enough for us atm. If we need more, we can buy more.
const OPEN_CAGE_DATA_URI = 'https://api.opencagedata.com/geocode/v1/json'
//ke@inensus.com
const API_KEY = 'd47b759329df4ae39dc26862c7e2dc7f'

export const getGeoDataFromAddress = async (address) => {
    try {
        const params = { q: address, key: API_KEY }
        const { data } = await axios.get(OPEN_CAGE_DATA_URI, { params: { q: address, key: API_KEY } })
        return data.results
    } catch (error) {
        throw error
    }

}