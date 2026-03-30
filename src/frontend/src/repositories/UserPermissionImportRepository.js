import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  importUserPermissions(data) {
    return Client.post(`${resource}/user-permissions`, { data })
  },
}
