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
  delete(cityId) {
    return Client.delete(`${resource}/${cityId}`)
  },
}
