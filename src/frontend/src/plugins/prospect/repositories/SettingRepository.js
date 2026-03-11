import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/prospect/settings`

export default {
  getSyncSettings() {
    return Client.get(`${resource}/sync`)
  },
}
