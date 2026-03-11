import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/meters`

export default {
  update(meterId, params) {
    return Client.put(`${resource}/${meterId}/parameters/`, params)
  },
}
