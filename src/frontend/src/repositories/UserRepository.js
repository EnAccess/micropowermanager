import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/users`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(userData) {
    return Client.post(`${resource}`, userData)
  },
  put(userData) {
    return Client.put(`${resource}/${userData.id}`, userData)
  },
  putAddress(userData) {
    return Client.put(`${resource}/${userData.id}/addresses`, userData)
  },
  get(id) {
    return Client.get(`${resource}/${id}`)
  },
}
