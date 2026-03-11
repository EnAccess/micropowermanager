import Client from "@/repositories/Client/AxiosClient.js"

export const resource = `/api/mpm-plugins`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
