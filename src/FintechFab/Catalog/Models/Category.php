<?php


namespace FintechFab\Catalog\Models;

/**
 * Class Category
 *
 * @package FintechFab\Catalog\Models
 *
 * @property integer       $id
 * @property integer       $level
 * @property integer       $order
 * @property integer       $parent_id
 * @property string        $path
 * @property string        $path_full
 * @property string        $name
 * @property integer       $enabled
 * @property integer       $deleted
 * @property integer       $category_type_id
 * @property integer       $symlink_id
 * @property integer       $left
 * @property integer       $right
 * @property string        $sid
 * @property string        $code
 *
 * @property Category      $symlink
 * @property Category      $parent
 * @property Category[]    $children
 * @property CategoryType  $type
 * @property Category[]    $descendants
 * @property CategoryTag[] $tags
 *
 * @method static Category whereParentId($parent_id)
 * @method static Category whereSymlinkId($symlink_id)
 * @method static Category whereLevel($level)
 * @method static Category find($id)
 * @method static Category first()
 * @method static Category whereCode($code)
 * @method static Category whereSid($sid)
 * @method static Category whereDeleted($boolean)
 * @method static Category whereEnabled($boolean)
 * @method static Category notDeleted()
 * @method static Category deleted()
 * @method static Category enabled()
 * @method static Category disabled()
 * @method static Category tagged($tags)
 * @method static Category typed($types)
 * @method static Category orderLeft()
 * @method static Category parentMargin($category)
 * @method Category newInstance()
 */
class Category extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'categories';

    public $fillable = ['name', 'path', 'path_full', 'level', 'sid', 'order', 'parent_id', 'code'];

	public function type()
	{
		return $this->belongsTo(CategoryType::class, 'category_type_id', 'id');
	}


	public function tags()
	{
		return $this->belongsToMany(CategoryTag::class, 'category_tag_rel');
	}

	public function parent()
	{
		return $this->hasOne(self::class, 'id', 'parent_id');
	}

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

	public function symlink()
	{
		return $this->hasOne(self::class, 'id', 'symlink_id');
	}

	public function descendants()
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('order');
	}

	public function resetLevelCascade()
	{
		foreach ($this->descendants as $cat) {
			$cat->level = $this->level + 1;
			$cat->save();
			$cat->resetLevelCascade();
		}
	}

	public function toggleEnabled()
	{
		$this->enabled = $this->enabled
			? 0
			: 1;
		$this->save();
	}

    public function enable()
    {
        $this->enabled = 1;
        $this->save();
    }

	public function remove()
	{
		$this->deleted = 1;
		$this->save();
	}

	public function setSymlink($id)
	{
		$this->symlink_id = $id;
		$this->save();
	}

	public function sysName()
	{
		return '[' . $this->id . '] ' . e($this->name);
    }


    /**
     * @param Category         $query
     * @param Category|integer $category
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeParentMargin($query, $category)
    {
        $category = is_numeric($category)
            ? self::find($category)
            : $category;

        return $query
            ->where('left', '>', $category->left)
            ->where('left', '<', $category->right);

    }

    /**
     * @param Category                                    $query
     * @param CategoryTag|CategoryTag[]|integer|integer[] $tags
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeTagged($query, $tags)
    {
        $tagIds = [];
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        foreach ($tags as $tag) {
            if (!is_numeric($tag)) {
                $tag = $tag->id;
            }
            $tagIds[] = $tag;
        }
        $ids = CategoryTagRel::whereIn('category_tag_id', $tagIds)->get()->lists('category_id');
        if (!$ids) {
            return $query->where(0, 1);
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * @param Category                                      $query
     * @param CategoryType|CategoryType[]|integer|integer[] $types
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeTyped($query, $types)
    {
        $ids = [];
        if (!is_array($types)) {
            $types = [$types];
        }
        foreach ($types as $type) {
            if (!is_numeric($type)) {
                $type = $type->id;
            }
            $ids[] = $type;
        }

        return $query->whereIn('category_type_id', $ids);
    }

    /**
     * @param Category $query
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeNotDeleted($query)
    {
        return $query->whereDeleted(0);
    }

    /**
     * @param Category $query
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeDeleted($query)
    {
        return $query->whereDeleted(1);
    }

    /**
     * @param Category $query
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeEnabled($query)
    {
        return $query->whereEnabled(1);
    }

    /**
     * @param Category $query
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeDisabled($query)
    {
        return $query->whereEnabled(0);
    }

    /**
     * @param Category $query
     *
     * @return \FintechFab\Catalog\Models\Category
     */
    public function scopeOrderLeft($query)
    {
        return $query->orderBy('left');
    }

    /**
     * @return string
     */
    public function typeName()
    {
        return $this->category_type_id ? $this->type->name : '';
    }

    /**
     * @return string
     */
    public function symlinkName()
    {
        return $this->symlink_id ? $this->symlink->name : '';
    }

    /**
     * rebuild full path for children recursively
     */
    public function setFullPath2Children()
    {
        if (0 < count($this->children)) {
            foreach ($this->children as $item) {
                $pathFull = trim($this->path_full . '/' . $item->path, '/');
                $item->path_full = $pathFull;
                $item->save();
                $item->setFullPath2Children();
            }
        }
	}

}