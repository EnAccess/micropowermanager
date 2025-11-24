import Client from "@/repositories/Client/AxiosClient"

class ProtectedPagePasswordResetRepository {
  async sendResetEmail(email) {
    return await Client.post(`/api/protected-page-password/reset`, {
      email,
    })
  }

  async validateToken(token) {
    return await Client.get(`/api/protected-page-password/validate/${token}`)
  }

  async resetPassword(data) {
    return await Client.post(`/api/protected-page-password/confirm`, data)
  }
}

export default new ProtectedPagePasswordResetRepository()
