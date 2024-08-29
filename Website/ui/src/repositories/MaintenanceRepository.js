import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = {
  list: `${baseUrl}/api/maintenance`,
  create: `${baseUrl}/api/maintenance/user`,
}

export default {
  list() {
    return Client.get(`${resource.list}`)
  },
  create(personalData) {
    return Client.post(`${resource.create}`, personalData)
  },
}
