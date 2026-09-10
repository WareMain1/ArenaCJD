-- Migración simplificada para soporte de período de gracia y resolución automática en torneos
-- 1. Período de gracia en minutos por torneo (por defecto 60 minutos)
ALTER TABLE `torneos`
  ADD COLUMN IF NOT EXISTS `periodo_gracia_resultado` INT(10) UNSIGNED NOT NULL DEFAULT 60 AFTER `cupo_maximo`;

-- 2. Estados ampliados en enfrentamientos (en_periodo_gracia y pendiente_revision)
ALTER TABLE `enfrentamientos`
  MODIFY COLUMN `estado` ENUM('pendiente','programado','en_curso','en_periodo_gracia','pendiente_revision','finalizado','cancelado') NOT NULL DEFAULT 'pendiente';
