import Client from './Client/AxiosClient'

const resource = '/api/countries'

export default {

    list () {
        return Client.get(`${resource}?page=1&per_page=15`)
    }

}
