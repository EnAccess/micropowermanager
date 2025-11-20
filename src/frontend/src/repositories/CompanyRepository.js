import Client from "@/repositories/Client/AxiosClient"

export const resource = `/api/companies`

export default {
  create(companyPM) {
    return Client.post(`${resource}`, companyPM)
  },
  get(user) {
    return Client.get(`${resource}/${user.email}`)
  },
}
