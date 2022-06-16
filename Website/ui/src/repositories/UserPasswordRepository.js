import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/users/password`

export default {

    put (userData) {
        return Client.put(`${resource}/${userData.id}`, userData)
    },
    //forgotPassword
    post (email) {
        return Client.post(`${resource}`,{ email: email })
    }
}
