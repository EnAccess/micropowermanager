import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/users/password`

export default {
  put(userData) {
    return Client.put(`${resource}/${userData.id}`, userData)
  },
  //forgotPassword
  post(email) {
    return Client.post(`${resource}`, { email: email })
  },
  validateToken(token) {
    return Client.get(`${resource}/validate/${token}`)
  },
  confirmReset(data) {
    return Client.post(`${resource}/confirm`, data)
  },
}
