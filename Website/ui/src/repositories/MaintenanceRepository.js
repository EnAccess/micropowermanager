import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = {
    'list':  `${baseUrl}/api/maintenance`,
    'create': `${baseUrl}/api/maintenance/user`,
}

export default {

    list () {
        return Client.get(`${resource.list}`)
    },
    create (personalData) {
        return Client.post(`${resource.create}`, personalData)
    }

}
