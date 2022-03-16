import Client from './Client/AxiosClient'
const resource = '/api/people'

export default {

    list (userId, page) {
        return Client.get(`${resource}/${userId}/transactions?page=${page}`)

    },
}
