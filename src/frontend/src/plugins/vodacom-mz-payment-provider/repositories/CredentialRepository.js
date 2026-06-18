import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/vodacom_mz/credentials`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
