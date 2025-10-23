import axios from "axios"
import { baseUrl } from "@/repositories/Client/AxiosClient"

class ProtectedPagePasswordResetRepository {
  constructor() {
    this.baseUrl = baseUrl
  }

  async sendResetEmail(email) {
    return await axios.post(
      `${this.baseUrl}/api/protected-page-password/reset`,
      {
        email,
      },
    )
  }

  async validateToken(token) {
    return await axios.get(
      `${this.baseUrl}/api/protected-page-password/validate/${token}`,
    )
  }

  async resetPassword(data) {
    return await axios.post(
      `${this.baseUrl}/api/protected-page-password/confirm`,
      data,
    )
  }
}

export default new ProtectedPagePasswordResetRepository()
