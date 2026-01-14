import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/africas-talking/credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  update(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
