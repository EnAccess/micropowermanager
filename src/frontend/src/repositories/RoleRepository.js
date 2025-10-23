import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

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
  assignToUser(roleName, userId) {
    return Client.post(
      `${resource}/${encodeURIComponent(roleName)}/assign/user/${userId}`,
    )
  },
  removeFromUser(roleName, userId) {
    return Client.delete(
      `${resource}/${encodeURIComponent(roleName)}/assign/user/${userId}`,
    )
  },
}
