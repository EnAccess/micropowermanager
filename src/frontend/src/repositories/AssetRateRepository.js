import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/assets/rates`

export default {
  update(id, terms) {
    return Client.put(`${resource}/${id}`, terms)
  },
}
