import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/appliances/rates`

export default {
  update(id, terms) {
    return Client.put(`${resource}/${id}`, terms)
  },
}
