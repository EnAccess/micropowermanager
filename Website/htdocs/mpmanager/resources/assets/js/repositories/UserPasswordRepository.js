const resource = '/api/users/password'
import Client from './Client/AxiosClient'

export default {

    put (userData) {
        return Client.put(`${resource}/${userData.id}`, userData)
    },
    //forgotPassword
    post (email) {
        return Client.post(`${resource}`,{ email: email })
    }
}
