import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/targets`

export default {
  store(target) {
    return Client.post(`${resource}`, target)
  },
}
