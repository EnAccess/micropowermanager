import RoleRepository from "@/repositories/RoleRepository.js"

export class RoleService {
  constructor() {
    this.roles = []
    this.userRoles = []
  }

  async fetchAll() {
    const res = await RoleRepository.all()
    this.roles = res.data
  }

  async fetchUserRoles(userId) {
    const res = await RoleRepository.userRoles(userId)
    this.userRoles = res.data
  }
}
