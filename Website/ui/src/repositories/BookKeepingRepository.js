import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/tickets/api/export`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    download(id, reference) {
        return `${resource}/download/` + `${id}` + `${reference}`
    },
}
