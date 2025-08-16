using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ASPNET.Models;

[Table("ecc_users")]
public class User
{
    [Key]
    [Column("id")]
    public int Id { get; set; }
    
    [Required]
    [StringLength(100)]
    [Column("first_name")]
    public string FirstName { get; set; } = string.Empty;
    
    [Required]
    [StringLength(100)]
    [Column("email")]
    public string Email { get; set; } = string.Empty;
    
    [StringLength(20)]
    [Column("phone")]
    public string? Phone { get; set; }
    
    [Column("created_at")]
    public DateTime? CreatedAt { get; set; }
    
    [Column("updated_at")]
    public DateTime? UpdatedAt { get; set; }
}