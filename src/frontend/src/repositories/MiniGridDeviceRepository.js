import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/mini-grids`

export default {
  list(miniGridId) {
    return Client.get(`${resource}/${miniGridId}/devices`)
  },
}
