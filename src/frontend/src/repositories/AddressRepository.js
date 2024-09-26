import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/people`

import Client from "@/repositories/Client/AxiosClient"

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(newAddress, personId) {
    return Client.post(`${resource}/${personId}/addresses`, newAddress)
  },
  show() {
    return Client.get(`${resource}`)
  },
  update(newAddress, personId) {
    return Client.put(`${resource}/${personId}/addresses`, newAddress)
  },
}
