import Client from "@/repositories/Client/AxiosClient"

export const resource = `/api/mpm-plugins`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
