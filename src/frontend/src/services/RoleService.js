import RoleRepository from "@/repositories/RoleRepository"

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

  async assignToUser(roleName, userId) {
    await RoleRepository.assignToUser(roleName, userId)
  }

  async removeFromUser(roleName, userId) {
    await RoleRepository.removeFromUser(roleName, userId)
  }
}
