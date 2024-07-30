import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

export const resource = `${baseUrl}/api/registration-tails`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(Id, tail) {
    return Client.put(`${resource}/${Id}`, tail)
  },
}
