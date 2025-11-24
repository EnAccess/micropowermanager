import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/transactions`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  analytics(period) {
    return Client.get(`${resource}/analytics/${period}`)
  },
  filteredList(term) {
    return Client.post(`${resource}/advanced`, term)
  },
  get(id) {
    return Client.get(`${resource}/${id}`)
  },
}
