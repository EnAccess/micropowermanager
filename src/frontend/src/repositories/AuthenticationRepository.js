const resource = `/api/auth`

import Client from "@/repositories/Client/AxiosClient.js"

export default {
  login(user) {
    return Client.post(`${resource}/login`, user)
  },
  refresh() {
    return Client.post(`${resource}/refresh`, null)
  },
}
