-- Indeks pada Foreign Keys & Pivot Tables
CREATE INDEX idx_users_id_ulid_team_id_status ON users(id, ulid, current_team_id, status);
CREATE INDEX idx_role_users ON role_users(user_id, role_id);
CREATE INDEX idx_permission_role_role_perm ON permission_role(permission_id, role_id);

-- Indeks Kunci Utama / Foreign Key standar
CREATE INDEX idx_role_users_role_id ON role_users(role_id);
CREATE INDEX idx_permission_role_perm_id ON permission_role(permission_id);

CREATE OR REPLACE VIEW v_user_permissions AS
SELECT 
    u.id AS user_id,
    u.ulid AS user_ulid,
    u.name AS user_name,
    u.current_team_id AS current_team_id,
    r.id AS role_id,
    r.name AS role_name,
    r.slug AS role_slug,
    p.id AS permission_id,
    p.name AS permission_name
FROM users u
INNER JOIN role_users ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
INNER JOIN permission_role rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE u.status = 1;