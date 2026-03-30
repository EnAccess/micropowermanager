import Client, { baseUrl } from "@/repositories/Client/AxiosClient.js"

const resource = `${baseUrl}/api/roles`

export default {
  all() {
    return Client.get(`${resource}/details`)
  },
  permissions() {
    return Client.get(`${resource}/permissions`)
  },
  userRoles(userId) {
    return Client.get(`${resource}/user/${userId}`)
  },
}
