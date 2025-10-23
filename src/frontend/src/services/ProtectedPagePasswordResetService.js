import { ErrorHandler } from "@/Helpers/ErrorHandler"
import ProtectedPagePasswordResetRepository from "@/repositories/ProtectedPagePasswordResetRepository"

export class ProtectedPagePasswordResetService {
  constructor() {
    this.repository = ProtectedPagePasswordResetRepository
  }

  async sendResetEmail(email) {
    try {
      const { data, status, error } =
        await this.repository.sendResetEmail(email)
      if (status !== 200) {
        throw new ErrorHandler(error, "http", status)
      }
      return data.data
    } catch (e) {
      const errorMessage =
        e.response?.data?.data?.message ||
        e.response?.data?.message ||
        "Failed to send reset email"
      throw new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }

  async validateToken(token) {
    try {
      const { data, status, error } = await this.repository.validateToken(token)
      if (status !== 200) {
        throw new ErrorHandler(error, "http", status)
      }
      return data.data
    } catch (e) {
      const errorMessage =
        e.response?.data?.data?.message ||
        e.response?.data?.message ||
        "Invalid or expired token"
      throw new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }

  async resetPassword(token, password, passwordConfirmation) {
    try {
      const { data, status, error } = await this.repository.resetPassword({
        token,
        password,
        password_confirmation: passwordConfirmation,
      })
      if (status !== 200) {
        throw new ErrorHandler(error, "http", status)
      }
      return data.data
    } catch (e) {
      const errorMessage =
        e.response?.data?.data?.message ||
        e.response?.data?.message ||
        "Failed to reset password"
      throw new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }
}
