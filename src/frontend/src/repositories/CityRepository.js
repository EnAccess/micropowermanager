import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/cities`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(city) {
    return Client.post(`${resource}`, city)
  },
  update(cityId, city) {
    return Client.put(`${resource}/${cityId}`, city)
  },
  delete(cityId, params) {
    return Client.delete(`${resource}/${cityId}`, { data: params })
  },
  linkedAddresses(cityId) {
    return Client.get(`${resource}/${cityId}/addresses`)
  },
}
