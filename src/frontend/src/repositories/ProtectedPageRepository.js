import Client from "@/repositories/Client/AxiosClient"

export const resource = `/api/protected-pages`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  compare(data) {
    return Client.post(`${resource}/compare`, data)
  },
}
