import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/people`

export default {
  get(page = 1) {
    return Client.get(`${resource}/${page}`)
  },
  update(person) {
    return Client.put(`${resource}/${person.id}`, person)
  },
  create(agentPm) {
    return Client.post(`${resource}`, agentPm)
  },
  delete(personId) {
    return Client.delete(`${resource}/${personId}`)
  },
  search(params) {
    return Client.get(`${resource}/search`, params)
  },
}
