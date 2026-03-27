import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/cities`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  detail(cityId, withRelation = false) {
    const query = withRelation ? "?relation=1" : ""
    return Client.get(`${resource}/${cityId}${query}`)
  },
  create(city) {
    return Client.post(`${resource}`, city)
  },
  update(cityId, city) {
    return Client.put(`${resource}/${cityId}`, city)
  },
}
