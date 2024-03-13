import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/auth`

import Client from './Client/AxiosClient'

export default {
    login(user) {
        return Client.post(`${resource}/login`, user)
    },
    refresh(token) {
        return Client.post(`${resource}/refresh`, null, {
            headers: { Authorization: 'Bearer' + token },
        })
    },
}
