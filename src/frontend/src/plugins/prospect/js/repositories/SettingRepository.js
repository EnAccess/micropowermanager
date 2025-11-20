import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/prospect/settings`

export default {
  getSyncSettings() {
    return Client.get(`${resource}/sync`)
  },
}
