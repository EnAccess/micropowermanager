import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/tickets/api`

export default {

    list () {
        return Client.get(`${resource}/users`)
    },
    create (userPM) {

        return Client.post(`${resource}/tickets/users`, userPM)
    }

}
