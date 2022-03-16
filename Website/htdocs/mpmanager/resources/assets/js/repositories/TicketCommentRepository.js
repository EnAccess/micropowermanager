const resource = '/tickets/api/tickets/comments'
import Client from './Client/AxiosClient'

export default {

    create (commentPm) {

        return Client.post(`${resource}`, commentPm)
    }
}
