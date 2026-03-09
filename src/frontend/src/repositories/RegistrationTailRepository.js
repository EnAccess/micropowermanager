import Client from "@/repositories/Client/AxiosClient.js"

export const resource = `/api/registration-tails`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(Id, tail) {
    return Client.put(`${resource}/${Id}`, tail)
  },
}
