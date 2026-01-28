import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/import`

export default {
  importUserPermissions(data) {
    return Client.post(`${resource}/user-permissions`, { data })
  },
}
