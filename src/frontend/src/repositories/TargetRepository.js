import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/targets`

export default {
  store(target) {
    return Client.post(`${resource}`, target)
  },
}
