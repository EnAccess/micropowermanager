const resource = '/tickets/api'
import Client from './Client/AxiosClient'

export default {

    list () {
        return Client.get(`${resource}/users`)
    },
    create (userPM) {

        return Client.post(`${resource}/tickets/users`, userPM)
    }

}
